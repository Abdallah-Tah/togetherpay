/* eslint-disable no-console */
/**
 * Seeds Firestore with an example group and member.
 *
 * Usage:
 *   node firebase/scripts/seedFirestore.js
 *
 * Requires:
 *   - firebase-tools logged in (firebase login)
 *   - Firestore enabled on the target project
 */
const admin = require('firebase-admin');

async function main() {
  admin.initializeApp();
  const db = admin.firestore();

  const groupId = '6';
  const groupRef = db.collection('groups').doc(groupId);

  await groupRef.set(
    {
      name: 'Mother Medics',
      ownerId: '1',
      timezone: 'America/New_York',
      currency: 'USD',
      cadence: 'MONTHLY',
      dueDay: 1,
      graceDays: 7,
      totalAmountCents: 15000,
      status: 'active',
      createdAt: admin.firestore.Timestamp.fromDate(new Date('2024-10-31T00:00:00Z')),
    },
    { merge: true }
  );

  await groupRef.collection('members').doc('member-ikram').set(
    {
      userId: '6',
      email: 'ikram.barkad@test.com',
      preferredChannel: 'CASH_APP',
      shareType: 'AMOUNT',
      shareValue: 50,
      cashTag: '$IkramBarkad',
      status: 'active',
    },
    { merge: true }
  );

  console.log('Seeded group 6 with one member.');
}

main()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error('Failed to seed Firestore:', err);
    process.exit(1);
  });
