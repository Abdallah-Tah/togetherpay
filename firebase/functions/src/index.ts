import * as functions from 'firebase-functions';
import * as admin from 'firebase-admin';

admin.initializeApp();

const db = admin.firestore();
const REGION = process.env.FUNCTIONS_REGION ?? 'us-central1';

interface AddMemberPayload {
  email?: string;
  phone?: string;
  share_type?: string;
  share_value?: number;
  preferred_channel?: string;
  venmo_username?: string;
  cash_tag?: string;
  cash_phone?: string;
  cash_email?: string;
  status?: string;
}

export const addGroupMember = functions
  .region(REGION)
  .https.onCall(async (data, context) => {
    if (!context.auth) {
      throw new functions.https.HttpsError(
        'unauthenticated',
        'Authentication is required.'
      );
    }

    const { groupId, payload } = data ?? {};
    if (!groupId) {
      throw new functions.https.HttpsError('invalid-argument', 'groupId is required.');
    }
    if (!payload || typeof payload !== 'object') {
      throw new functions.https.HttpsError('invalid-argument', 'payload is required.');
    }

    const normalizedPayload: AddMemberPayload = { ...payload };
    const email = (normalizedPayload.email ?? '').toString().trim().toLowerCase();
    const phone = (normalizedPayload.phone ?? '').toString().trim();

    if (!email && !phone) {
      throw new functions.https.HttpsError(
        'invalid-argument',
        'An email or phone number is required.'
      );
    }

    const groupRef = db.collection('groups').doc(String(groupId));
    const groupSnap = await groupRef.get();
    if (!groupSnap.exists) {
      throw new functions.https.HttpsError(
        'not-found',
        `Group ${groupId} was not found.`
      );
    }

    // Ensure the group is owned by the caller if the payload requires owner privileges.
    const groupData = groupSnap.data() ?? {};
    const callerUid = context.auth.uid;
    if (
      groupData.ownerId &&
      `${groupData.ownerId}` !== callerUid &&
      groupData.ownerUid &&
      groupData.ownerUid !== callerUid
    ) {
      throw new functions.https.HttpsError(
        'permission-denied',
        'Only the group owner can add members.'
      );
    }

    let userId: string | null = null;
    let inviteCreated = false;

    const usersRef = db.collection('users');
    if (email) {
      const existingByEmail = await usersRef
        .where('email', '==', email)
        .limit(1)
        .get();
      if (!existingByEmail.empty) {
        userId = existingByEmail.docs[0].id;
      }
    }

    if (!userId && phone) {
      const existingByPhone = await usersRef
        .where('phone', '==', phone)
        .limit(1)
        .get();
      if (!existingByPhone.empty) {
        userId = existingByPhone.docs[0].id;
      }
    }

    const now = admin.firestore.FieldValue.serverTimestamp();

    if (!userId) {
      const placeholderUser = await usersRef.add({
        email: email || null,
        phone: phone || null,
        invitedAt: now,
        invitationStatus: 'pending',
        createdBy: callerUid,
      });
      userId = placeholderUser.id;
      inviteCreated = true;
    }

    const memberRef = groupRef.collection('members').doc(userId);
    const memberSnap = await memberRef.get();
    if (memberSnap.exists) {
      throw new functions.https.HttpsError(
        'already-exists',
        'User is already a member of this group.'
      );
    }

    const memberData = {
      userId,
      email: email || null,
      phone: phone || null,
      preferred_channel:
        (normalizedPayload.preferred_channel as string)?.toUpperCase() ?? 'CASH_APP',
      share_type:
        (normalizedPayload.share_type as string)?.toUpperCase() ?? 'AMOUNT',
      share_value:
        typeof normalizedPayload.share_value === 'number'
          ? normalizedPayload.share_value
          : Number.parseFloat(String(normalizedPayload.share_value ?? 0)) || 0,
      venmo_username: normalizedPayload.venmo_username ?? null,
      cash_tag: normalizedPayload.cash_tag ?? null,
      cash_phone: normalizedPayload.cash_phone ?? null,
      cash_email: normalizedPayload.cash_email ?? null,
      status: normalizedPayload.status ?? (inviteCreated ? 'invited' : 'active'),
      createdAt: now,
      updatedAt: now,
    };

    await memberRef.set(memberData, { merge: true });

    // Optional: record invite doc for follow-up notifications.
    if (inviteCreated) {
      const inviteRef = groupRef.collection('invites').doc(userId);
      await inviteRef.set(
        {
          userId,
          email: email || null,
          phone: phone || null,
          invitedAt: now,
          invitedBy: callerUid,
          status: 'pending',
        },
        { merge: true }
      );
    }

    return {
      message: inviteCreated
        ? 'Invite sent and member added to the group.'
        : 'Member added successfully',
      member: {
        id: userId,
        ...memberData,
      },
      inviteCreated,
    };
  });
